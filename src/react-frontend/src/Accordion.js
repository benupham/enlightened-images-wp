import React, { useEffect, useState, useRef, useLayoutEffect } from "react"
import styled from "styled-components"

const AccordionTitle = styled.span`
  cursor: pointer;
`

const AccordionContent = styled.div`
  height: ${({ height }) => height}px;
  opacity: ${({ height }) => (height > 0 ? 1 : 0)};
  overflow: hidden;
  transition: 0.5s;
`

export const Accordion = ({ title, open, children }) => {
  const content = useRef(null)
  const [height, setHeight] = useState(0)
  const [direction, setDirection] = useState("right")

  const toggleAccordion = () => {
    setHeight(height === 0 ? content.current.scrollHeight : 0)
    setDirection(height === 0 ? "down" : "right")
  }

  useEffect(() => {
    if (open) {
      toggleAccordion()
    }
  }, [open])

  return (
    <>
      <h3>
        <AccordionTitle onClick={toggleAccordion}>
          {title}
          <i className={`arrow-accordion ${direction}`}></i>
        </AccordionTitle>
      </h3>
      <AccordionContent height={height} ref={content}>
        {children}
      </AccordionContent>
    </>
  )
}

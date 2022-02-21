import React, { useEffect, useState, useRef } from "react"
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

export const Accordion = ({ title, setOpen, open, children }) => {
  const content = useRef(null)
  const [height, setHeight] = useState(0)
  const [direction, setDirection] = useState("right")

  useEffect(() => {
    if (open) {
      setHeight(content.current.scrollHeight)
      setDirection("down")
    } else {
      setHeight(0)
      setDirection("right")
    }
  }, [open, children])

  return (
    <div className="accordion">
      <h3>
        <AccordionTitle onClick={(e) => setOpen((prev) => !prev)}>
          {title}
          <i className={`arrow-accordion ${direction}`}></i>
        </AccordionTitle>
      </h3>
      <AccordionContent height={height} ref={content}>
        {children}
      </AccordionContent>
    </div>
  )
}

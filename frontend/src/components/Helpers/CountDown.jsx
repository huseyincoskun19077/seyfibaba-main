"use client";
/* eslint-disable no-underscore-dangle */
import { useEffect, useState } from "react";

function CountDown(lastDate) {
  const [showDate, setDate] = useState(0);
  const [showHour, setHour] = useState(0);
  const [showMinute, setMinute] = useState(0);
  const [showSecound, setDateSecound] = useState(0);
  const [isExpired, setIsExpired] = useState(false);

  const provideDate = new Date(lastDate);
  const year = provideDate.getFullYear();
  const month = provideDate.getMonth();
  const date = provideDate.getDate();
  const hours = provideDate.getHours();
  const minutes = provideDate.getMinutes();
  const seconds = provideDate.getSeconds();

  const _seconds = 1000;
  const _minutes = _seconds * 60;
  const _hours = _minutes * 60;
  const _date = _hours * 24;

  const resetToZero = () => {
    setDate(0);
    setHour(0);
    setMinute(0);
    setDateSecound(0);
    setIsExpired(true);
  };

  useEffect(() => {
    if (!lastDate) {
      resetToZero();
      return undefined;
    }

    const targetMs = new Date(year, month, date, hours, minutes, seconds).getTime();
    if (!Number.isFinite(targetMs)) {
      resetToZero();
      return undefined;
    }

    const tick = () => {
      const distance = targetMs - Date.now();
      if (distance <= 0) {
        resetToZero();
        return false;
      }

      setIsExpired(false);
      setDate(Math.floor(distance / _date));
      setHour(Math.floor((distance % _date) / _hours));
      setMinute(Math.floor((distance % _hours) / _minutes));
      setDateSecound(Math.floor((distance % _minutes) / _seconds));
      return true;
    };

    if (!tick()) return undefined;

    const timer = setInterval(() => {
      if (!tick()) clearInterval(timer);
    }, 1000);

    return () => clearInterval(timer);
  }, [lastDate, year, month, date, hours, minutes, seconds]);

  return { showDate, showHour, showMinute, showSecound, isExpired };
}

export default CountDown;
